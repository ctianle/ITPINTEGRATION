#!/bin/bash

# List of services and their respective keywords to monitor
SERVICES=(
        "Keystrokes.service:Trying to unpickle estimator LogisticRegression"
        "Screenshots.service:warnings.warn"
        "Audio.service:Loading winfo"
        "Webcam.service:start capture"
)

# Log file for tracking service readiness
LOG_FILE="/home/raspberry/service_check.log"

# Clear the log file at the start
> "$LOG_FILE"

# Array to track services that are already confirmed as ready
READY_SERVICES=()

# Record the script's start time
START_TIME=$(date +%s)

# Function to check service readiness based on log content
check_service_ready() {
    local service_name=$(echo "$1" | cut -d: -f1)
    local keyword=$(echo "$1" | cut -d: -f2)

    # Use journalctl to look for the specific log entry
    if journalctl -u "$service_name" --boot | grep -q "$keyword"; then
        return 0 # Ready
    else
        return 1 # Not ready
    fi
}

# Write initial message to the log file
echo "$(date): Starting service readiness check..." >> "$LOG_FILE"

# Monitor services until all are ready
while true; do
    ALL_READY=true
    for SERVICE_ENTRY in "${SERVICES[@]}"; do
        SERVICE_NAME=$(echo "$SERVICE_ENTRY" | cut -d: -f1)

        # Skip services already marked as ready
        if [[ " ${READY_SERVICES[*]} " == *"$SERVICE_NAME"* ]]; then
            continue
        fi

        KEYWORD=$(echo "$SERVICE_ENTRY" | cut -d: -f2)

        if check_service_ready "$SERVICE_ENTRY"; then
                        # Calculate time taken for this service to become ready
            CURRENT_TIME=$(date +%s)
            TIME_TAKEN=$((CURRENT_TIME - START_TIME))
            echo "$(date): $SERVICE_NAME is fully ready (found '$KEYWORD' in logs). Time taken: ${TIME_TAKEN} seconds." >> "$LOG_FILE"
            READY_SERVICES+=("$SERVICE_NAME") # Mark this service as ready
        else
            echo "$(date): $SERVICE_NAME is not ready yet (waiting for '$KEYWORD' in logs)." >> "$LOG_FILE"
            ALL_READY=false
        fi
    done

    # Break the loop if all services are ready
    if [ ${#READY_SERVICES[@]} -eq ${#SERVICES[@]} ]; then
        echo "$(date): All services are ready. Starting RPI_Keyboard.py." >> "$LOG_FILE"
        break
    fi

    # Wait for a while before rechecking
    sleep 15
done

# Run the Python script after all services are up
sudo python3 /home/raspberry/RPI_Keyboard.py >> "$LOG_FILE" 2>&1
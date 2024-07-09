Invoke-WebRequest -Uri http://192.168.18.2:80/ -Method POST -Body ($data|ConvertTo-Json) -ContentType "application/json"

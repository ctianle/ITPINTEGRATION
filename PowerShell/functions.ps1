# Functions.ps1

function Is_Connected {
    $check = Get-PnpDevice -PresentOnly | Where-Object { $_.InstanceId -match '^USB\\VID_1D6B' }
    if ($check) {
        Write-Host "Raspberry Pi connected." -ForegroundColor Green
        return 'FOUND'
    } else {
        Write-Host "Raspberry Pi not connected." -ForegroundColor Red
        return $null
    }
}

function Get-Encrypted-Key {
    try {
        $response = Invoke-RestMethod -Uri "$base_url/get_encrypted_key" -Method Get
        Write-Host "Fetched encrypted key from Flask server." -ForegroundColor Green
        return $response.encrypted_key
    } catch {
        Write-Error "Failed to get encrypted key from Flask server: $_"
        return $null
    }
}

function Get-C2-Cert {
    param (
        [string]$encrypted_key
    )
    try {
        $response = Invoke-RestMethod -Uri "$c2_root_ca_url" -Method Post -Body (@{ key = $encrypted_key } | ConvertTo-Json) -ContentType 'application/json'
        Write-Host "Fetched encrypted C2 root certificate." -ForegroundColor Green
        return $response.cert
    } catch {
        Write-Error "Failed to get encrypted C2 root certificate: $_"
        return $null
    }
}

function Verify-C2-Cert {
    param (
        [string]$encrypted_cert
    )
    try {
        $response = Invoke-RestMethod -Uri "$base_url/verify_c2_cert" -Method Post -Body (@{ cert = $encrypted_cert } | ConvertTo-Json) -ContentType 'application/json'
        Write-Host "Verified C2 root certificate on Flask server." -ForegroundColor Green
        return $response
    } catch {
        Write-Error "Failed to verify C2 root certificate on Flask server: $_"
        return $null
    }
}

function Fetch_CSR {
    param (
        [string]$studentid
    )
    try {
        $response = Invoke-RestMethod -Uri "$base_url/get_csr" -Method Get -Body @{ studentid = $studentid } -ContentType "application/json"
        Write-Host "Fetched CSR from Flask server." -ForegroundColor Green
        return $response
    } catch {
        Write-Error "Failed to fetch CSR from Flask server: $_"
        return $null
    }
}

function Send_CSR {
    param (
        [PSCustomObject]$csr_response
    )
    try {
        $response = Invoke-RestMethod -Uri $c2_signing_url -Method Post -Body ($csr_response | ConvertTo-Json) -ContentType 'application/json'
        Write-Host "Sent CSR to C2 Server and received signed certificate." -ForegroundColor Green
        Write-Host $response
        return $response
    } catch {
        Write-Error "Failed to send CSR to C2 Server: $_"
        return $null
    }
}

function Send_Signed_Cert {
    param (
        [string]$signed_cert
    )
    try {
        $response = Invoke-RestMethod -Uri "$base_url/receive_cert" -Method Post -Body (@{ cert = $signed_cert } | ConvertTo-Json) -ContentType 'application/json'
        Write-Host "Sent signed certificate back to Flask server." -ForegroundColor Green
        return $response
    } catch {
        Write-Error "Failed to send signed certificate to Flask server: $_"
        return $null
    }
}

function Check_Cert {
    try {
        $response = Invoke-RestMethod -Uri "$base_url/check_cert" -Method Get
        Write-Host "Checked for existing certificate on Flask server." -ForegroundColor Green
        return $response
    } catch {
        Write-Error "Failed to check for certificate on Flask server: $_"
        return $null
    }
}

function Verify_Cert {
    param (
        [PSCustomObject]$cert_response
    )
    try {
        $response = Invoke-RestMethod -Uri $c2_verify_url -Method Post -Body ($cert_response | ConvertTo-Json) -ContentType 'application/json'
        Write-Host "Verified certificate with C2 Server." -ForegroundColor Green
        return $response
    } catch {
        Write-Error "Failed to verify certificate with C2 Server: $_"
        return $null
    }
}

function Check_CA_Cert {
    try {
        $encrypted_key = Get-Encrypted-Key
        if (-not $encrypted_key) {
            Write-Host "Failed to get encrypted Fernet key. Exiting function."
            return $null
        }

        $encrypted_cert = Get-C2-Cert -encrypted_key $encrypted_key
        if (-not $encrypted_cert) {
            Write-Host "Failed to get encrypted C2 root certificate. Exiting function."
            return $null
        }

        $response = Verify-C2-Cert -encrypted_cert $encrypted_cert
        if ($response.status -eq "new_cert_needed") {
            Write-Host "New root certificate needed. Proceeding with Handle_Certificates_Once."
            return $response.status
        } elseif ($response.status -eq "valid") {
            Write-Host "C2 root certificate is valid."
            return $response.status
        } else {
            Write-Error "Failed to verify C2 root certificate. Exiting function."
            return $null
        }
    } catch {
        Write-Error "An error occurred during CA certificate handling: $_"
        return $null
    }
}

function Send_Token_To_Flask {
    param (
        [string]$combined_encrypted_data
    )
    try {
        $response = Invoke-RestMethod -Uri "$base_url/receive_token" -Method Post -Body (@{ data = $combined_encrypted_data } | ConvertTo-Json) -ContentType 'application/json'
        Write-Host "Redirected Encrypted Token data to Flask server." -ForegroundColor Green
        return $response
    } catch {
        Write-Error "Failed to redirect combined encrypted data to Flask server: $_"
        return $null
    }
}

function Handle_Certificates_Once {
    param ($studentid)
    if (Is_Connected -ne $null) {
        try {
            $ca_cert_status = Check_CA_Cert
            if ($ca_cert_status -eq "new_cert_needed") {
                $csr_response = Fetch_CSR -studentid $studentid
                if ($csr_response -eq $null) {
                    Write-Error "Failed to fetch CSR. Exiting certificate handling process."
                    return
                }

                $signed_cert_response = Send_CSR -csr_response $csr_response
                if ($signed_cert_response -eq $null) {
                    Write-Error "Failed to get signed certificate. Exiting certificate handling process."
                    return
                }

                $signed_cert = $signed_cert_response.cert
                $send_cert_response = Send_Signed_Cert -signed_cert $signed_cert
                if ($send_cert_response -eq $null) {
                    Write-Error "Failed to send signed certificate to Flask server. Exiting certificate handling process."
                    return
                }

                Write-Host "Certificate handling process completed successfully." -ForegroundColor Green
            } elseif ($ca_cert_status -eq "valid") {
                $cert_response = Check_Cert
                if ($cert_response.status -eq "cert_exists") {
                    $verify_response = Verify_Cert -cert_response $cert_response
                    if ($verify_response.status -eq "success") {
                        Write-Host "Certificate is valid." -ForegroundColor Green
                        
                        # Redirect the response to Flask server
                        $redirect_response = Send_Token_To_Flask -combined_encrypted_data $verify_response.message
                        if ($redirect_response -eq $null) {
                            Write-Error "Failed to redirect response to Flask server."
                        }
                        return 
                    } elseif ($verify_response.status -eq "error") {
                        switch ($verify_response.message) {
                            "Certificate has been revoked." {
                                Write-Error "Certificate has been revoked. Aborting process."
                                return
                            }
                            "Invalid signature." {
                                Write-Error "Certificate signature is invalid. Proceeding with certificate handling process."
                            }
                            "Timestamp is outside the allowed time window." {
                                Write-Error "Certificate timestamp is invalid. Proceeding with certificate handling process."
                            }
                            default {
                                Write-Error "Certificate is not valid. Proceeding with certificate handling process."
                            }
                        }
                    }
                } else {
                    Write-Error "No certificate found on Flask server. Proceeding with certificate handling process."
                }

                $csr_response = Fetch_CSR -studentid $studentid
                if ($csr_response -eq $null) {
                    Write-Error "Failed to fetch CSR. Exiting certificate handling process."
                    return
                }

                $signed_cert_response = Send_CSR -csr_response $csr_response
                if ($signed_cert_response -eq $null) {
                    Write-Error "Failed to get signed certificate. Exiting certificate handling process."
                    return
                }

                $signed_cert = $signed_cert_response.cert
                $send_cert_response = Send_Signed_Cert -signed_cert $signed_cert
                if ($send_cert_response -eq $null) {
                    Write-Error "Failed to send signed certificate to Flask server. Exiting certificate handling process."
                    return
                }

                Write-Host "Certificate handling process completed successfully." -ForegroundColor Green
            } else {
                Write-Error "Failed to handle CA certificate."
            }
        } catch {
            Write-Error "An error occurred during the certificate handling process: $_"
        }
    }
}
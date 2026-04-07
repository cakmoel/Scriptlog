#!/bin/bash

# ============================================================================
# Socrates Blade v3.2 - URL Validation Library
# Contains URL validation and error handling functions
# ============================================================================

# Source this file to get URL validation functions
# Example: source url-validator-lib.sh

# --- URL Validation Configuration ---
: "${MAX_URL_RETRIES:=3}"
: "${URL_CHECK_TIMEOUT:=5}"

# ============================================================================
# URL Validation Functions
# ============================================================================

extract_hostname() {
    local url="$1"
    local hostname=""
    
    hostname=$(echo "$url" | sed -E 's|^https?://||' | cut -d':' -f1 | cut -d'/' -f1)
    
    declare -g LAST_VALIDATED_HOSTNAME="$hostname"
    echo "$hostname"
}

check_dns_resolution() {
    local hostname="$1"
    local resolved=false
    
    if getent hosts "$hostname" > /dev/null 2>&1; then
        resolved=true
    elif ping -c 1 -W 1 "$hostname" > /dev/null 2>&1; then
        resolved=true
    elif host "$hostname" > /dev/null 2>&1; then
        resolved=true
    fi
    
    echo "$resolved"
}

test_http_connection() {
    local url="$1"
    local timeout="${2:-$URL_CHECK_TIMEOUT}"
    local response=""
    local http_code=""
    
    if command -v curl &> /dev/null; then
        http_code=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout "$timeout" -L "$url" 2>/dev/null || echo "000")
        echo "$http_code"
    else
        echo "curl_not_found"
    fi
}

test_https_ssl() {
    local url="$1"
    local timeout="${2:-$URL_CHECK_TIMEOUT}"
    
    if command -v curl &> /dev/null; then
        curl -s -o /dev/null --connect-timeout "$timeout" -w "%{http_code}" "$url" 2>&1 | grep -q "SSL" && echo "ssl_error" && return 0
        curl -s -o /dev/null --connect-timeout "$timeout" -w "%{http_code}" "$url" 2>&1 | grep -q "certificate" && echo "cert_error" && return 0
        echo "ssl_ok"
    else
        echo "curl_not_found"
    fi
}

detect_known_typos() {
    local hostname="$1"
    local suggestions=()
    
    case "$hostname" in
        bloware*)
            suggestions+=("blogware.site (Did you mean 'blogware' instead of 'bloware'?)")
            ;;
        blogware*)
            suggestions+=("localhost (for local testing)")
            ;;
        loclahost)
            suggestions+=("localhost (typo detected)")
            ;;
        localhose)
            suggestions+=("localhost (typo detected)")
            ;;
        localhot)
            suggestions+=("localhost (typo detected)")
            ;;
    esac
    
    printf '%s\n' "${suggestions[@]}"
}

show_url_suggestions() {
    local hostname="${1:-$LAST_VALIDATED_HOSTNAME}"
    local error_type="$2"
    
    if [ -z "$hostname" ]; then
        hostname="$LAST_VALIDATED_HOSTNAME"
    fi
    
    echo ""
    echo -e "${YELLOW}=== Suggestions ===${NC}"
    echo ""
    
    case "$error_type" in
        dns_fail)
            echo -e "${YELLOW}The hostname '${hostname}' could not be resolved.${NC}"
            echo ""
            echo -e "${BOLD}Possible solutions:${NC}"
            echo "  1. Check if the hostname is correct (no typos?)"
            
            local typos
            typos=$(detect_known_typos "$hostname")
            if [ -n "$typos" ]; then
                echo ""
                echo -e "${CYAN}Possible typo corrections:${NC}"
                echo "$typos" | sed 's/^/   - /'
            fi
            
            echo ""
            echo "  2. For local testing, use: ${GREEN}http://localhost${NC}"
            echo ""
            if grep -q "127.0.0.1.*$hostname" /etc/hosts 2>/dev/null; then
                echo -e "${GREEN}* '$hostname' is configured in /etc/hosts${NC}"
            else
                echo -e "${CYAN}* Add to /etc/hosts: 127.0.0.1 $hostname${NC}"
            fi
            ;;
        connection_refused)
            echo -e "${YELLOW}Connection to '${hostname}' was refused.${NC}"
            echo ""
            echo -e "${BOLD}Possible solutions:${NC}"
            echo "  1. Make sure the web server is running"
            echo "  2. Check if the correct port is being used (default: 80/443)"
            echo "  3. Check firewall settings"
            ;;
        timeout)
            echo -e "${YELLOW}Connection to '${hostname}' timed out.${NC}"
            echo ""
            echo -e "${BOLD}Possible solutions:${NC}"
            echo "  1. Check network connectivity"
            echo "  2. Check firewall/proxy settings"
            echo "  3. Try increasing timeout with --timeout option"
            ;;
        ssl_error)
            echo -e "${YELLOW}SSL certificate verification failed for '${hostname}'.${NC}"
            echo ""
            echo -e "${BOLD}Possible solutions:${NC}"
            echo "  1. Use HTTP instead: ${GREEN}http://${hostname}${NC}"
            echo "  2. Add --no-verify-ssl flag (if supported)"
            echo "  3. Install valid SSL certificate"
            ;;
    esac
    
    echo ""
}

validate_target_url() {
    local url="$1"
    local hostname=""
    local dns_resolved=""
    local http_code=""
    local result=0
    declare -g URL_ERROR=""
    declare -g URL_ERROR_TYPE=""
    
    hostname=$(extract_hostname "$url")
    
    if [ -z "$hostname" ]; then
        URL_ERROR="Could not extract hostname from URL"
        URL_ERROR_TYPE="invalid_url"
        return 1
    fi
    
    echo -e "${BLUE}* Validating target: ${hostname}...${NC}"
    
    dns_resolved=$(check_dns_resolution "$hostname")
    
    if [ "$dns_resolved" != "true" ]; then
        URL_ERROR="DNS resolution failed: Name or service not known for '$hostname'"
        URL_ERROR_TYPE="dns_fail"
        return 1
    fi
    
    echo -e "${BLUE}  - DNS resolution: OK${NC}"
    
    if [[ "$url" == https://* ]]; then
        local ssl_result
        ssl_result=$(test_https_ssl "$url")
        if [ "$ssl_result" = "ssl_error" ] || [ "$ssl_result" = "cert_error" ]; then
            URL_ERROR="SSL certificate verification failed"
            URL_ERROR_TYPE="ssl_error"
            result=1
        fi
    fi
    
    http_code=$(test_http_connection "$url")
    
    if [ "$http_code" = "curl_not_found" ]; then
        echo -e "${YELLOW}  ! curl not found, skipping HTTP check${NC}"
        return 0
    fi
    
    if [ "$http_code" = "000" ]; then
        URL_ERROR="Connection refused or timed out (HTTP code: $http_code)"
        URL_ERROR_TYPE="timeout"
        return 1
    fi
    
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 400 ]; then
        echo -e "${GREEN}  - HTTP connection: OK (HTTP $http_code)${NC}"
    elif [ "$http_code" -eq 400 ]; then
        echo -e "${YELLOW}  ! HTTP 400 Bad Request (server reachable)${NC}"
    elif [ "$http_code" -eq 401 ]; then
        echo -e "${YELLOW}  ! HTTP 401 Unauthorized (server reachable)${NC}"
    elif [ "$http_code" -eq 403 ]; then
        echo -e "${YELLOW}  ! HTTP 403 Forbidden (server reachable)${NC}"
    elif [ "$http_code" -eq 404 ]; then
        echo -e "${YELLOW}  ! HTTP 404 Not Found (server reachable)${NC}"
    elif [ "$http_code" -ge 500 ]; then
        echo -e "${YELLOW}  ! HTTP $http_code Server Error (server reachable)${NC}"
    fi
    
    return $result
}

prompt_for_url() {
    local attempt=1
    local new_url=""
    local hostname=""
    
    while [ $attempt -le $MAX_URL_RETRIES ]; do
        echo ""
        echo -e "${YELLOW}Attempt $attempt of $MAX_URL_RETRIES${NC}"
        echo ""
        
        if [ -n "$URL_ERROR" ]; then
            echo -e "${RED}Error: $URL_ERROR${NC}"
            show_url_suggestions "$hostname" "$URL_ERROR_TYPE"
        fi
        
        echo -e "${BOLD}Please enter a valid target URL:${NC}"
        echo -e "Examples:"
        echo -e "  - ${GREEN}http://localhost${NC}"
        echo -e "  - ${GREEN}http://127.0.0.1${NC}"
        echo -e "  - ${GREEN}http://blogware.site${NC}"
        echo ""
        read -r -p "URL> " new_url
        
        if [ -z "$new_url" ]; then
            echo -e "${RED}URL cannot be empty${NC}"
            ((attempt++))
            continue
        fi
        
        if ! [[ "$new_url" =~ ^https?:// ]]; then
            new_url="http://$new_url"
        fi
        
        hostname=$(extract_hostname "$new_url")
        
        if validate_target_url "$new_url"; then
            TARGET_URL="$new_url"
            echo ""
            echo -e "${GREEN}* URL validated successfully!${NC}"
            return 0
        fi
        
        ((attempt++))
    done
    
    return 1
}

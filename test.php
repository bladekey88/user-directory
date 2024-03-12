<?php

function parse_config_file($filepath)
{
    $virtual_hosts = [];
    $current_host = null;
    $server_name = null;

    try {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $index => $line) {
            # General Setup and formatting
            // Skip any commented out lines
            if (strpos($line, "#") !== false) {
                continue;
            }
            $line = trim($line);

            # Processing 
            // The <VirtualHost> tag is the beginning of a block, 
            // so need to parse that for information for arrays
            if (strpos($line, "<VirtualHost") !== false) {
                $current_host = substr($line, 1, -1);
                if (!isset($virtual_hosts[$current_host])) {
                    $virtual_hosts[$current_host] = [];
                }
            }
            // When we reach the close tag, clear the variables to prevent scoping weirdness
            elseif (strpos($line, "</VirtualHost>") !== false) {
                $current_host = null;
                $server_name = null;
            }
            // For any other line type, parse it into the array
            else {
                // Use  the ServerName as the subarray key to
                // handle multiple entites per virtualhost
                if (strpos($line, "ServerName") !== false) {
                    list($attribute_label, $server_name) = explode(" ", $line, 2);
                    $server_name = trim($server_name);

                    if (!isset($virtual_hosts[$current_host][$server_name])) {
                        $virtual_hosts[$current_host][$server_name] = [];
                    }
                    if (!array_key_exists($server_name, $virtual_hosts[$current_host][$server_name])) {
                        $virtual_hosts[$current_host][$server_name] = [$attribute_label => $server_name];
                    }
                } elseif ($current_host) {
                    if (strpos($line, "</Directory>") !== false) {
                        continue;
                    }
                    list($attribute, $attribute_value) = explode(" ", $line, 2);
                    $attribute = htmlspecialchars(trim($attribute));
                    $attribute_value = trim($attribute_value);
                    if (!isset($virtual_hosts[$current_host][$server_name][$attribute])) {
                        $virtual_hosts[$current_host][$server_name][$attribute] = $attribute_value;
                    }
                }
            }
        }

        return $virtual_hosts;
    } catch (Exception $e) {
        echo "An error occurred: " . $e->getMessage();
        return [];
    }
}

function iterateNestedArray($array, $indent = 0)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            echo str_repeat("\t", $indent) . "Key: $key\n";
            iterateNestedArray($value, $indent + 1);
        } else {
            echo str_repeat("\t", $indent) . "Key: $key, Value: $value\n";
        }
    }
}

// Example usage:
$file_path = "C:\\WS\\Apache24\\conf\\extra\\httpd-vhosts.conf";
$hosts = parse_config_file($file_path);
print_r($hosts);
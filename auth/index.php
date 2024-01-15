<?php
function filterSSLServerKeys($serverArray)
{
    $filteredArray = array();

    foreach ($serverArray as $key => $value) {
        // Check if the key starts with "SSL_SERVER"
        if (strpos($key, 'SSL') === 0) {
            $filteredArray[$key] = $value;
        }
    }

    return $filteredArray;
}


function hasValidCert()
{
    if (
        !isset($_SERVER['SSL_CLIENT_M_SERIAL'])
        || !isset($_SERVER['SSL_CLIENT_V_END'])
        || !isset($_SERVER['SSL_CLIENT_VERIFY'])
        || $_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS'
        || !isset($_SERVER['SSL_CLIENT_I_DN'])
    ) {
        return false;
    }

    if ($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) {
        return false;
    }

    return true;
}
hasValidCert();
if (hasValidCert()) : ?>

    <pre>
<?php
    echo "<p>VALID</p>";
    $sslClientValues = filterSSLServerKeys($_SERVER);
    print_r($sslClientValues);
?>
</pre>
<?php else : ?>
    <p>Not Valid or Certificate Missing</p>
    <?php echo "SSL_CLIENT_VERIFY = " . $_SERVER["SSL_CLIENT_VERIFY"]; ?>
<?php endif; ?>
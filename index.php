<?php
ob_implicit_flush();
?>
    <form method="post">
        <table cellpadding="10" border="1">
            <tr>
                <td>
                    Port
                </td>
                <td>
                    <?php
                    for ($i = 1; $i <= 16; $i++) {
                        if ($i == $_POST['port'])
                            echo "<input type='radio' name='port' value='$i' checked>";
                        else
                            echo "<input type='radio' name='port' value='$i'>";
                        echo $i;
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    Command
                </td>
                <td>
                    <select name="command">
                        <option value="5">On All</option>
                        <option value="4" <?php if ($_POST['command'] == 4) echo "selected" ?>>Off All</option>
                        <option value="2" <?php if ($_POST['command'] == 2) echo "selected" ?>>On One</option>
                        <option value="1" <?php if ($_POST['command'] == 1) echo "selected" ?>>Off one</option>
                        <option value="3" <?php if ($_POST['command'] == 3) echo "selected" ?>>Invert One
                        </option>
                        <option value="6" <?php if ($_POST['command'] == 6) echo "selected" ?>>Invert All
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                </td>
                <td><input type="submit" name="submit"/>
                </td>
            </tr>
        </table>
    </form>
<?php

class TcpGpio
{
    private $service_port, $address;
    private $template = "0x55 0xaa 0x00 %s 0x00 %s %s %s";

    function __construct($ip, $port)
    {
        $this->service_port = $port;
        $this->address = $ip;
    }

    static function prettyHex($str)
    {
        $str = trim($str);
        // split a string of two characters
        $out = chunk_split(bin2hex($str), 2, ' ');
        // create an array from the string
        $out = explode(' ', $out);
        // we add to each element 0x
        foreach ($out as $k => $v) {
            if (!empty($v)) $out[$k] = '0x' . $out[$k];
        }
        // return an arrayed array
        return implode(" ", $out);
    }

    static function prettyBin($str)
    {
        //just in case, remove the blanks
        $str = trim($str);
        // break into an array
        $out = explode(' ', $str);

        //remove 0x
        foreach ($out as $k => $v) {
            if (!empty($v)) $out[$k] = str_replace(['0x', " "], "", $out[$k]);
        }
        //We collect without spaces
        $out = implode("", $out);
        //back to bin
        return hex2bin($out);
    }

    private function read($socket)
    {
        while ($buf = socket_read($socket, 1024, PHP_BINARY_READ)) {
            if ($buf = trim($buf))
                break;
        }

        return $buf;
    }

    private function prepare($params, $command)
    {
        if (is_array($params)) {
            $length = count($params) + 2;
            foreach ($params as &$p) {
                $p = $this->toHex($p);
            }

            $params = implode(" ", $params);
        } else {
            $length = 3;
            $params = $this->toHex($params);
        }
        $fullSum = $length + $params + $command;
        $length = $this->toHex($length);
        $fullSum = $this->toHex($fullSum);
        $command = $this->toHex($command);
        return $this->connect(sprintf($this->template, $length, $command, $params, $fullSum));
    }

    function connect($data)
    {
        /* Create a TCP / IP socket. */
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return false;
        }
        $result = socket_connect($socket, $this->address, $this->service_port);
        if ($result === false) {
            return false;
        }
        $auth = "admin\r\n";
        socket_write($socket, $auth, strlen($auth));

        $in = self::prettyBin($data);
        socket_write($socket, $in, strlen($in));
        $out = $this->read($socket);
        return self::prettyHex($out);
    }

    function toHex($dec)
    {
        if ($dec < 10) {
            return "0x0" . dechex($dec);
        } else {
            $hex = dechex($dec);
            if (strlen($hex) < 2) {
                $hex = "0" . $hex;
            }
            return "0x" . $hex;
        }
    }

    function gpioOff($num)
    {
        return $this->prepare($num, 1);
    }

    function gpioOn($num)
    {
        return $this->prepare($num, 2);
    }

    function gpioInvert($num)
    {
        return $this->prepare($num, 3);
    }

    function gpioOffAll()
    {
        return $this->prepare(0, 4);
    }

    function gpioOnAll()
    {
        return $this->prepare(0, 5);
    }

    function gpioInvertAll()
    {
        return $this->prepare(0, 6);
    }

    function readAll()
    {

        return $this->prepare(0, 10);
    }

    function doCommand($nums, $command)
    {

        return $this->prepare($nums, $command);
    }

}

if (isset($_POST['submit'])) {
    $TCP = new TcpGpio("192.168.1.6", "8899");
    /*Required bits        |n+2 | ID | com | The sum of all the information without the main      . n - number of parameters*/

    if ($_POST['command'] == 1) {
        $return = $TCP->gpioOff($_POST['port']);
    } elseif ($_POST['command'] == 2) {
        $return = $TCP->gpioOn($_POST['port']);
    } elseif ($_POST['command'] == 3) {
        $return = $TCP->gpioInvert($_POST['port']);
    } elseif ($_POST['command'] == 4) {
        $return = $TCP->gpioOffAll();
    } elseif ($_POST['command'] == 5) {
        $return = $TCP->gpioOnAll();
    } elseif ($_POST['command'] == 6) {
        $return = $TCP->gpioInvertAll();
    }
    if ($return)
        echo "<h1>$return</h1>";
    else
        echo "<h1>ERROR</h1>";
}
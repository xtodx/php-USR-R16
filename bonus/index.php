<form method="post">
    <table cellpadding="10" border="1">
        <tr>
            <td>
                Порт
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
                Команда
            </td>
            <td>
                <select name="command">
                    <option value="5">Включить все</option>
                    <option value="4" <?php if ($_POST['command'] == 4) echo "selected" ?>>Выключить все</option>
                    <option value="2" <?php if ($_POST['command'] == 2) echo "selected" ?>>Включить один</option>
                    <option value="1" <?php if ($_POST['command'] == 1) echo "selected" ?>>Выключит один</option>
                    <option value="3" <?php if ($_POST['command'] == 3) echo "selected" ?>>Инвертировать один</option>
                    <option value="6" <?php if ($_POST['command'] == 6) echo "selected" ?>>Инвертировать все</option>
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

if (isset($_POST['submit'])) {
    /*Обязательные биты        |n+2 | ID | com | Сумма всей инфы без основной      . n - колво параметрво*/
    $template = "0x55 0xaa 0x00 0x03 0x00 %s %s %s";
    $len = $_POST['port'] + $_POST['command'] + 3;
    $command = sprintf($template, toHex($_POST['command']), toHex($_POST['port']) . "", toHex($len) . "");
    echo $command;
    file_put_contents("command.txt", $command);
}
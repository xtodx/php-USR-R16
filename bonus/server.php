<?php

function prettyHex($str)
{
    $str = trim($str);
    // дробим строку по два символа
    $out = chunk_split(bin2hex($str), 2, ' ');
    // создаем массив из строки
    $out = explode(' ', $out);
    // добавляем к каждому элементу 0x
    foreach ($out as $k => $v) {
        if (!empty($v)) $out[$k] = '0x' . $out[$k];
    }
    // возвращаем схлопнутый в строку массив
    return implode(" ", $out);
}

function fromHex($str)
{
    //на всякий случай убираем пробелы
    $str = trim($str);
    //разбиваем на массив
    $out = explode(' ', $str);

    //убираем 0x
    foreach ($out as $k => $v) {
        if (!empty($v)) $out[$k] = str_replace(['0x', " "], "", $out[$k]);
    }
    //Собираем без пробелов
    $out = implode("", $out);
    //навад в бин
    return hex2bin($out);
}

function read($sock)
{
    while ($buf = socket_read($sock, 1024, PHP_BINARY_READ))
        if ($buf = trim($buf))
            break;

    return $buf;
}

error_reporting(E_ALL);

/* Позволяет скрипту ожидать соединения бесконечно. */
set_time_limit(0);

/* Включает скрытое очищение вывода так, что мы получаем данные
 * как только они появляются. */
ob_implicit_flush();

$address = '192.168.1.4';
$port = 8899;
$file = 'log.txt';
$command = 'command.txt';
file_put_contents($file, "");
file_put_contents($command, "");

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "Не удалось выполнить socket_create(): причина: " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "Не удалось выполнить socket_bind(): причина: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "Не удалось выполнить socket_listen(): причина: " . socket_strerror(socket_last_error($sock)) . "\n";
}
try {
    do {
        if (($msgsock = socket_accept($sock)) === false) {
            echo "Не удалось выполнить socket_accept(): причина: " . socket_strerror(socket_last_error($sock)) . "\n";
            break;
        }
        /* Отправляем инструкции. */
        $msg = "admin";
        socket_write($msgsock, $msg, strlen($msg));
        try {
            do {
                if (false === ($buf = read($msgsock))) {
                    echo "ERROR: $buf\n";
                    break 2;
                }
                if (!$buf = trim($buf)) {
                    continue;
                }

                $com = file_get_contents($command);
                if (strlen($com) > 0) {
                    //file_put_contents($command, "");
                } else {
                    $com = "0x55 0xaa 0x00 0x03 0x00 0x0a 0x01 0x89";
                }
                $talkback = fromHex($com);
                // Открываем файл для получения существующего содержимого
                $current = file_get_contents($file);
                // Добавляем новую информацию в файл
                $current .= "\r\n" . prettyHex($buf) . "\r\n$com\r\n -- \r\n";
                // Пишем содержимое обратно в файл
                file_put_contents($file, $current);
                sleep(1);
                socket_write($msgsock, $talkback, strlen($talkback));
            } while (true);
            socket_close($msgsock);
        } catch (Exception $e) {
            socket_close($msgsock);
        }
    } while (true);
    socket_close($sock);
} catch (Exception $e) {
    socket_close($sock);
}

?>
<?php

for ($angka = 1; $angka <= 100; $angka++) {
    if ($angka % 3 === 0 && $angka % 5 === 0) {
        echo "FizzBuzz\n";
    } elseif ($angka % 3 === 0) {
        echo "Fizz\n";
    } elseif ($angka % 5 === 0) {
        echo "Buzz\n";
    } else {
        echo "$angka\n";
    }
}

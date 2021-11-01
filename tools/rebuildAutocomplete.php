<?php

require_once __DIR__ . '/../lib/Core.php';

Log::notice('started');

DB::execute('truncate table Autocomplete');
DB::execute('insert into Autocomplete (formNoAccent, formUtf8General) ' .
            'select distinct binary formNoAccent, formUtf8General ' .
            'from Lexeme');

Log::notice('finished');

<?php

return [

    'micro' => [
        'name'    => 'Kredit Mikro',
        'desc'    => 'Untuk kebutuhan dana cepat dengan limit terjangkau.',
        'rate'    => 8.0,
        'limit'   => 25_000_000,
        'tenors'  => [6, 12],
    ],

    'regular' => [
        'name'    => 'Kredit Reguler',
        'desc'    => 'Pilihan seimbang dengan limit dan tenor lebih fleksibel.',
        'rate'    => 12.0,
        'limit'   => 100_000_000,
        'tenors'  => [12, 24, 36],
    ],

    'priority' => [
        'name'    => 'Kredit Prioritas',
        'desc'    => 'Limit besar dengan tenor panjang untuk kebutuhan besar.',
        'rate'    => 16.0,
        'limit'   => 300_000_000,
        'tenors'  => [24, 36, 48],
    ],

];

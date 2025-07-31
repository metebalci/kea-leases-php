# kea-leases-php

A simple php file to output kea leases csv files as html. This php file is created with ChatGPT.

Change the configuration on top of the leases.php according to your setup:

```
$leaseFileV4 = '/var/lib/kea/kea-leases4.csv';
$leaseFileV6 = '/var/lib/kea/kea-leases6.csv';
date_default_timezone_set("Europe/Zurich");
```

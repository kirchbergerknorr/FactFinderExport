Kirchbergerknorr FactFinderExport
=================================

FactFinderExport module is designed to add background export functionality to 
Flagbit FactFinder module.

Installation
------------

1. Add `require` and `repositories` sections to your composer.json as shown in example below and run `composer update`.
2. Configure options in in System -> Configuration -> Kirchbergerknorr -> FactFinderExport. 
3. Actiavate logs and module.

*composer.json example*

```
{
    ...
    
    "repositories": [
        {"type": "git", "url": "https://github.com/kirchbergerknorr/FactFinderExport"},
    ],
    
    "require": {
        "kirchbergerknorr/factfinder-export": "*"
    },
    
    ...
}
```

Usage
-----

Use shell-script to run export from commandline:

```
php factfinderexport.php
```


Support
-------

Please [report new bugs](https://github.com/kirchbergerknorr/FactFinderExport/issues/new).

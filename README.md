## old-frontend
This is Ripple's current frontend, written in PHP.
This is the only part of Ripple we've not rewritten since the first version of Ripple, and it sucks.
But, since the code is here just for reference, I don't think it's a huge problem.

## Installation
Copy config.sample.php as config.php and edit it
```
$ cd inc
$ cp config.sample.php config.php
$ nano config.php
```
Then, run composer install on the main directory
```
$ composer install
```

## License
All code in this repository is licensed under the GNU AGPL 3 License.  
See the "LICENSE" file for more information

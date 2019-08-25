# remove the old meos-mop folder from the web root
rm -rf /var/www/html/meos-mop

# make a new directory
mkdir /var/www/html/meos-mop

# copy all the files
cp -a . /var/www/html/meos-mop/

# delete unnecessary files
rm /var/www/html/meos-mop/config.dist.php
rm /var/www/html/meos-mop/setup.php
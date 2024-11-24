Due to the raspberry pi permissions for different usrs, to allow various scripts to perform their functionalities, it is needed to change the permissions of some folders. As the flaskserver is launched by the user www-data, we need to give permission to this user with the commands below.

sudo chown :www-data /etc/ssl
sudo chmod 775 /etc/ssl

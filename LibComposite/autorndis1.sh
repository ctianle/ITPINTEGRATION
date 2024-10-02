echo "dtoverlay=dwc2 | sudo tee -a /boot/config.txt"
echo "dwc2" | sudo tee -a /etc/modules
sudo echo "libcomposite" | sudo tee -a /etc/modules
sudo echo "dtoverlay=dwc2 >> /boot/firmware/config.txt
sudo reboot
# Install chrome
apt-get install -y wget
wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
apt-get install -S -y ./google-chrome-stable_current_amd64.deb
chrome --version
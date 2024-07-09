# Rapid-Dashboard
> RAPID Folder - Local Development Version <br>
> rapid-server - Sever Deployment Version
## Prerequisites
NetBeans - You need NetBeans IDE to run this project.

PHP Interpreter - You also need a PHP interpreter installed on your system.

## Testing functionality
Access the application in your browser.

eg. http://localhost:port/overview 

## Server Set-up Guide
1. Docker: <br>
curl -fsSL https://get.docker.com -o get-docker.sh <br>
sudo sh get-docker.sh <br>
sudo usermod -aG docker $USER 

**Restart Pi**

2. Docker-compose: <br>
sudo wget https://github.com/docker/compose/releases/download/v2.27.1/docker-compose-linux-aarch64 -O /usr/local/bin/docker-compose

sudo chmod +x /usr/local/bin/docker-compose

3. Making Directory
mkdir ~/rapid <br>
cd ~/rapid <br>
mkdir html <br>

~/rapid      <-- drop docker-compose.yml & nginx.conf here <br>
~/rapid/html <-- drop Rapid folder web files in here

4. Future mnt of physical drive <br>

Changes below <br>
```
db:
  image: mongo:latest
  restart: always
  environment:
    MONGO_INITDB_ROOT_USERNAME: myuser
    MONGO_INITDB_ROOT_PASSWORD: mypassword
    MONGO_INITDB_DATABASE: rapid
  volumes:
    - /mnt/mydrive:/data/db
  ports:
    - "27017:27017"
  networks:
    - mynetwork
```

# MongoDB
**Install the MongoDB 4.4 GPG key:**

wget -qO - https://www.mongodb.org/static/pgp/server-4.4.asc | sudo apt-key add -

**Add the source location for the MongoDB packages:**

echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu focal/mongodb-org/4.4 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-4.4.list

**Download the package details for the MongoDB packages:**

sudo apt-get update

**Install MongoDB:**

sudo apt-get install -y mongodb-org

**Creation of Collections**

docker-compose exec db bash -c 'mongosh -u myuser -p mypassword --authenticationDatabase admin'

Copy paste the script in /Schema/mongo-init.js

**Sample Insert and Query**

```
rapid> db.Users.insertOne({
...   UserType: "Admin",
...   UserName: "JohnDoe",
...   Email: "johndoe@example.com",
...   PasswordHash: "hashedpassword123"
... });
```
```
{
  acknowledged: true,
  insertedId: ObjectId('6671cae7c691c5198c8db5fb')
}
```
```
rapid> db.Users.findOne({ UserName: "JohnDoe" });
{
  _id: ObjectId('6671cae7c691c5198c8db5fb'),
  UserType: 'Admin',
  UserName: 'JohnDoe',
  Email: 'johndoe@example.com',
  PasswordHash: 'hashedpassword123'
}
```


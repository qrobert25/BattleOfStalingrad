# BattleOfStalingrad

Installation:

1. Run in the project folder the command: **docker compose up -d**
	(This step takes a long while the first time. It is because the setting of thecouchbase php extension)

2. Create the new Couchbase cluster in the URL: http://localhost:8091/ui/index.html  
    2.1. Click the button "Setup New Cluster"  
	2.2. Set the next values:  
		- Cluster Name: battleofstalingrad  
		- Create Admin Username: development  
		- Password: secret  
	2.3. Click the button "Next" and accept the terms  
	2.4. Click the button "Finish With Defaults"  
	2.5. Now, into the console, click on the side bar "Buckets", and then in the top-right corner click "Add Bucket"  
	2.6 Add the bucket with name: battleofstalingrad  

3. In the project folder run the command: **docker exec -it battle-of-stalingrad-app-1 bash**
4. Run following the command into the container: **php artisan db:seed --class=GameSeeder**
5. Go to the URL: http://localhost:8080/ and enjoy the game!!

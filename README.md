StatusCake-CloudFlare
=====================

Using Statuscake's monitoring and Cloudflare to achieve high availability.

A PHP web hook to handle down notifications from Statuscake. If a Down notification is received, it'll change the A record of a domain on cloudflare to a backup IP set by you.

<br><br>
License: MIT<br>
Dependencies: Web Server, PHP, php-curl lib
<br><br>

Setup: <br>
1. I suggest you rename the php file to something obscure. <br>
2. Add / change the global vars section. It consists of credentials and other information that needs to be modified to your needs. <br>
3. Upload this script to the desired server, make sure the script is accessible via the internet. <br>
4. On StatusCake: <br>
	Go into group contacts and add the ping URL. This URL is the URL of your PHP script that you just uploaded to your server in step 3.<br>
	Make sure the domains you're monitoring has the correct contact group (a group that contains the ping URL).
5. It's recommended that you have more than the PHP script as an alert type, so you can switch it back to the primary server once it's up. With this setup, a website shouldn't have a downtime of more than ~6 minutes, assuming your DNS provider does not cache queries.
<br><br>
You can reach me on <a href="http://twitter.com/blackdotsh/"> twitter </a>.
<br><br>
If you're looking for UptimeRobot + Cloudflare, you can find it <a href="https://github.com/blackdotsh/UptimeFlare"> here </a>.


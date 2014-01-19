# plzasm Assembly Bot

Tweet an x86 instruction like "JMP EAX" to [@plzasm](https://twitter.com/plzasm) and it will assemble it for you. 

**Disclaimer**: This project is a work in progress. Please use the issue tracker
to report any enhancements or issues you encounter.

## Dependencies

You will need a x86/x64 CPU. You will need to install gcc and objdump too. 

The assembly bot requires two libraries, which are included in this project. 

The first library is created by [Taylor Hornby](https://defuse.ca). He created the assembly library that does the heavy work.

The second library is tmhOAuth. This lets us access the Twitter api. The library requires php5-curl and curl. Please install these with your package manager. 

Go here for more information on [tmhOAuth](https://github.com/themattharris/tmhOAuth).

## Usage

Using this bot should be easy. You just have to put your Twitter api keys in plzasm.php and type `php plzasm.php` in 
a command line.

1. Put Twitter api keys in plzasm.php.
2. Create the log file plzasm.log in /var/log/ hint: do (`chown plzasm:plzasm /var/log/plzasm.log` once you created the user and group.)
3. `php plzasm.php` as root so it can access the log file. (Read on if you don't want to run as root.)

We included an optional [Debian service file](https://github.com/redragonx/plzasm/blob/master/plzasm) so you can use the assembly bot as a daemon. 

1. Create a simple user/group named plzasm. The user does not need a home dir or default bash access.
2. To create a user group, do `groupadd plzasm` as root.
3. To create the user with the group you just made, do `useradd -g plzasm plzasm`
4. Run `chown plzasm:plzasm /var/log/plzasm.log` once you created the user and group.
2. You place the service file in /etc/init.d/ with the root user. 
2. Run the following command as root `update-rc.d plzasm defaults`. This will start the bot on system reboots too.
3. Run the bot with this command `service plzasm start`

## Change History

First version release.

## Community

License: AGPL, LGPL, and Apache 2. See included license files.

Follow [@Redragonx](https://twitter.com/intent/follow?screen_name=redragonx) or [@DefuseSec](https://twitter.com/intent/follow?screen_name=defusesec) to receive updates on releases, or ask for support.

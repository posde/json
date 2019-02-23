# json
## JSON provider for LinuxMCE

More and more people want to have access to LinuxMCEs data via JSON. This code provides

1. Access to the MySQL data via filters
2. Access to the router's memory space to get information about now playing and playlist for specific Entertainment Areas. The [JSON Plugin](http://wiki.linuxmce.org/index.php/JSON_Plugin)  is needed for this to work.

## How to call
`json.php` will give you a list of tables supported. Tables refer to the information available to be queried.

`json.php?table=rooms` will give you a list of rooms.

`json.php?table=rooms&key=1` will just give you room 1.
## List of supported tables
1. now_playing
2. playlist
3. rooms
4. ea
5. lights
6. drapes
7. phones
8. cameras

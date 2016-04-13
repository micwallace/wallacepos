/**
 * WPOS Websocket update relay, node.js sever.
 * @type {*}
 */

//var fs = require('fs');
/*var options = {
    key: fs.readFileSync('/etc/apache2/certs/wallacepos.com-ssl-wildcard.key').toString(),
    cert: fs.readFileSync('/etc/apache2/certs/wallacepos-com-ssl-wildcard.crt').toString(),
    ca: fs.readFileSync('/etc/apache2/certs/sub.class2.code.ca.crt').toString()
};*/
var http = require('http');
var app = http.createServer(wshandler);

app.listen(8080, '127.0.0.1');

io = require('socket.io').listen(app);

function wshandler(req, res) {
    // socket handler; do nothing
}

var devices = {};
var sessions = {};

var hashkey = "dgqsy8DgvyKl6RhCngOuFzNosbnThPZnMHCpZZm58GGb7Nnr2Y1tzVVudRBAj1ad"; // key for php interaction, provides extra security

io.sockets.on('connection', function (socket) {
    // START AUTHENTICATION
    var cookies = null;
    var authed = false;
    // check for session cookie
    if (socket.handshake.hasOwnProperty('headers')) {
        if (socket.handshake.headers.hasOwnProperty('cookie')) {
            cookies = socket.handshake.headers.cookie;
            if (cookies.indexOf("PHPSESSID=") !== -1) { // trim up to our cookie value
                cookies = cookies.substr(cookies.indexOf("PHPSESSID=") + 10, cookies.length);
                if (cookies.indexOf(";") !== -1) { // trim off other cookies
                    cookies = cookies.substr(0, cookies.indexOf(";"));
                }
            }
            if (sessions.hasOwnProperty(cookies)) {
                authed = true;
                // Request device registration
                socket.emit('updates', {a: "regreq", data: ""});
                console.log("Authorised by session: " + cookies);
            }
        }
    }
    // check for hashkey (for php authentication)
    if (cookies == null) {
        if (socket.handshake.query.hasOwnProperty('hashkey')) {
            if ((hashkey == socket.handshake.query.hashkey) && (socket.handshake.address.address=="127.0.0.1")) {
                authed = true;
                console.log("Authorised by hashkey: " + socket.handshake.query.hashkey);
            }
        }
    }
    // Disconnect if not authenticated
    if (!authed) {
        socket.emit('updates', {a: "error", data: "Socket authentication failed!"});
        socket.disconnect();
    }

    // broadcast to all connected sockets
    socket.on('broadcast', function (data) {
        socket.broadcast.emit('updates', data);
    });

    // send to certain auth'd devices based on device id's provided.
    socket.on('send', function (data) {
        // if device.include is null, send to all auth'd
        var inclall = data.include == null;
        for (var i in devices) {
            if (inclall || (data.include.hasOwnProperty(i) > 0)) {
                io.sockets.socket(devices[i].socketid).emit('updates', data.data);
            } else {
                console.log(i + " not in devicelist, " + JSON.stringify(data.include) + "; discarding.");
            }
        }
        // send to the admin dash
        if (devices.hasOwnProperty(0)) {
            // send updated device list to admin dash
            io.sockets.socket(devices[0].socketid).emit('updates', data.data);
        }
    });

    socket.on('session', function (data) {
        // check for hashkey
        if (hashkey == data.hashkey) {
            if (data.remove==false){
                sessions[data.data] = true;
                console.log("Added PHP session: " + data.data);
            } else {
                if (sessions.hasOwnProperty(data.data)){
                    delete(sessions[data.data]);
                    console.log("Removed PHP session: " + data.data);
                }
            }
        } else {
            console.log("Send request not processed, no valid hashkey!");
        }
    });

    // register device details
    socket.on('reg', function (request) {
        // register device
        devices[request.deviceid] = {};
        devices[request.deviceid].socketid = socket.id;
        devices[request.deviceid].username = request.username;
        // remove device on disconnect
        socket.on('disconnect', function () {
            delete(devices[request.deviceid]);
            if (request.deviceid != 0) {
                if (devices.hasOwnProperty(0)) {
                    // send updated device list to admin dash
                    io.sockets.socket(devices[0].socketid).emit('updates', {a: "devices", data: JSON.stringify(devices)});
                }
            }
        });
        if (devices.hasOwnProperty(0)) {
            // send updated device list to admin dash
            io.sockets.socket(devices[0].socketid).emit('updates', {a: "devices", data: JSON.stringify(devices)});
        }
        console.log("Device registered");
    });
});
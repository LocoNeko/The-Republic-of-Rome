var io = require('socket.io')(8080);
console.log('Started Republic of Rome node socket.io server');

io.on('connection', function(socket){
    socket.on('Update', function(gameId){
        setTimeout(function () {
            socket.broadcast.emit('Update' , gameId);
            console.log('Update from Game : ', gameId);
        }, 250);
    });
});
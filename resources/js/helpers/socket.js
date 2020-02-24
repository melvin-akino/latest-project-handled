const getSocketKey = (socketMessage) => {
    return Object.keys(JSON.parse(socketMessage))[0]
}

const getSocketValue = (socketMessage, socketKey) => {
    return JSON.parse(socketMessage)[socketKey]
}

module.exports = {
    getSocketKey, getSocketValue
}

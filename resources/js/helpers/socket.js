const getSocketKey = (socketMessage) => {
    try {
        return Object.keys(JSON.parse(socketMessage))[0]
    } catch(e) {
        return false
    }
}

const getSocketValue = (socketMessage, socketKey) => {
    return JSON.parse(socketMessage)[socketKey]
}

module.exports = {
    getSocketKey, getSocketValue
}

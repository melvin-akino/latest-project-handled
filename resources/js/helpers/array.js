const sortByObjectKeys = (object, sortedObject) => {
    Object.keys(object).sort().map(key => {
        if(typeof(sortedObject) == "undefined") {
            sortedObject = {}
        }
        sortedObject[key] = object[key]
    })
    return sortedObject
}

module.exports = {
    sortByObjectKeys
}

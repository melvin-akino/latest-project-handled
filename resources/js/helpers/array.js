const sortByObjectKeys = (object, sortedObject, property = null) => {
    Object.keys(object).sort().map(key => {
        if(typeof(sortedObject) == "undefined") {
            sortedObject = {}
        }
        sortedObject[key] = object[key]
        if(property) {
            sortedObject[key] = sortByObjectProperty(sortedObject[key], property)
        }
    })
    return sortedObject
}

const sortByObjectProperty = (array, property) => {
    return array.sort((a, b) => (a[property] > b[property]) ? 1 : -1)
}

const moveToFirstElement = (array, property, itemToCheck) => {
    let newArray = array.filter(item => item[property] != itemToCheck)
    let elementToGoFirst = array.filter(item => item[property] == itemToCheck)[0]
    newArray.unshift(elementToGoFirst)
    return newArray
}

module.exports = {
    sortByObjectKeys,
    sortByObjectProperty,
    moveToFirstElement
}

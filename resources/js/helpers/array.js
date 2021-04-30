const sortByObjectKeys = (object, property = null, equalValueProperty = null) => {
    let sortedObject = {}
    Object.keys(object).sort().map(key => {
        sortedObject[key] = object[key]
        if(property) {
            if(equalValueProperty) {
                sortedObject[key] = sortByObjectProperty(sortedObject[key], property, equalValueProperty)
            } else {
                sortedObject[key] = sortByObjectProperty(sortedObject[key], property)
            }
        }
    })
    return sortedObject
}

const sortByObjectProperty = (array, property, equalValueProperty = null) => {
    if(equalValueProperty) {
        return array.sort((a, b) => (a[property] > b[property]) ? 1 : (a[property] === b[property]) ? ((a[equalValueProperty] > b[equalValueProperty]) ? 1 : -1) : -1)
    } else {
        return array.sort((a, b) => (a[property] > b[property]) ? 1 : -1)
    }
}

const moveToFirstElement = (array, property, sortProperty = null, itemToCheck) => {
    let newArray = array.filter(item => item[property] != itemToCheck)
    if(sortProperty) {
        newArray = array.filter(item => item[property] != itemToCheck).sort((a,b) => (a[sortProperty] > b[sortProperty]) ? 1 : -1 )
    } else {
        newArray = array.filter(item => item[property] != itemToCheck)
    }
    let elementToGoFirst = array.filter(item => item[property] == itemToCheck)[0]
    if(elementToGoFirst) {
        newArray.unshift(elementToGoFirst)
    }
    return newArray
}

module.exports = {
    sortByObjectKeys,
    sortByObjectProperty,
    moveToFirstElement
}

const twoDecimalPlacesFormat = (value) => {
    if(value != null) {
        if(typeof(value)=="number") {
            return value.toFixed(2)
        } else {
            return
        }
    }
}

const moneyFormat = (value) => {
    if(value != null) {
        return value.toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,')
    }
}

const convertPointAsNumeric = (points, oddType) => {
    if(oddType == 'HDP' || oddType == 3 || oddType == 'HT HDP' || oddType == 11) {
        return Number(points)
    } else if(oddType=='OU' || oddType == 4 || oddType == 'HT OU' || oddType == 12) {
        return  Number(points.split(' ')[1])
    } else {
        return
    }
}

module.exports = {
    twoDecimalPlacesFormat,
    moneyFormat,
    convertPointAsNumeric
}

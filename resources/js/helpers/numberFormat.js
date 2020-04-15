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

module.exports = {
    twoDecimalPlacesFormat,
    moneyFormat
}

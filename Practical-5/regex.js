const nameRegex = /^[A-Za-z ]{2,50}$/;
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const mobileRegex = /^[6-9][0-9]{9}$/;
const passwordRegex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/;

function validateName(name) {
    return nameRegex.test(name.trim());
}

function validateEmail(email) {
    return emailRegex.test(email.trim());
}

function validateMobile(mobile) {
    return mobileRegex.test(mobile.trim());
}

function validatePassword(password) {
    return passwordRegex.test(password);
}

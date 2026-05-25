

// Check input validity
document.addEventListener('DOMContentLoaded', ()=> MapsTo('state-identity'));
// State management (UI Cards)
function MapsTo(stateId){
    const statesList = document.querySelectorAll('.state');
    statesList.forEach(st => 
        st.classList.add('d-none'));
        
    const target = document.getElementById(stateId);
    if(target){
        target.classList.remove('d-none');
    }
}

const backBtn = document.querySelectorAll('.back-btn');
backBtn.forEach(btn => {
btn.addEventListener('click',(ele)=>{
    MapsTo('state-identity');
    ele.preventDefault();}) 
}
)

function updateDisplays(value){
    const accountNames = document.querySelectorAll('.display-email-target');
    accountNames.forEach(accountName => {
        accountName.textContent = value;
    })
}


const checkBtn = document.querySelector('#auth-btn');
const InputEmail = document.querySelector('#InputEmail');

checkBtn.addEventListener('click', (e)=> {
    e.preventDefault();
    const emailValue = InputEmail.value.trim()
    updateDisplays(emailValue);
    if(checkValidateOf(InputEmail)){
        signUpData.email = emailValue;
        signUpData.action = "check_email";
        
        toggleLoading(checkBtn, true)
        setTimeout( async () => {
            const response = await checkFromServer(signUpData);
            
            if(response.status ==="taken"){
                MapsTo('state-login');
            }else if (response.status === 'available'){
                MapsTo('state-signUp');
            }else if (response.status === 'invalid') {
            setInvalidInput(InputEmail);
            // Maybe show the response.message in a div
}
            toggleLoading(checkBtn, false)
                }, 1000);
    }
    
})
// input fiels validity 
function checkValidateOf(input){
    if(!input.checkValidity()){
       setInvalidInput(input);
        return false;
    }else{
        setValidInput(input)
        return true;
    }
}

// validateInput(InputEmail);
InputEmail.addEventListener('input',() => {
    checkValidateOf(InputEmail);
}
);



// Hundle login logic
const loginData = {
    email : '',
    password :'',
    rememberMeStatus : false
};

const loginBtn = document.querySelector('#login-btn');
const loginInputPassword = document.querySelector('#login-password');
// loginInputPassword.addEventListener('input', () => checkValidateOf(loginInputPassword));


const showPassBtn = document.querySelector('#show-login-password');
const loginPassinput = document.querySelector('#login-password');
showPassBtn.addEventListener('click', ()=>{
    if(loginPassinput.type === 'password'){
        loginPassinput.type = 'text';
    }else{
        loginPassinput.type = 'password'
    };
})

// Remember me checkbox
const rememberMe = document.querySelector('#remember-me-checkbox');
rememberMe.addEventListener('change', (ev)=> {
        if(ev.target.checked){
            loginData.rememberMeStatus = true
        }else{
            loginData.rememberMeStatus = false
        }
    })

const logInBtn = document.querySelector('#login-btn');
loginBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const passValue = loginPassinput.value.trim();
    if(checkValidateOf(loginPassinput)){
        loginData.email = signUpData.email;
        loginData.password = passValue;
        loginData.action = "login";
        toggleLoading(logInBtn, true);
        
        setTimeout(async ()=> {
            const response = await checkFromServer(loginData)
            console.log(loginData);
            if(response.status === 'success'){
                window.location.href = "dashboard.php"
            }else{
                setInvalidInput(loginPassinput);
            }
            
            toggleLoading(logInBtn, false);
        }, 1000)
        

    }
})

// Hundle sign up logic
const signUpData = {
    ...loginData,
    email :'',
}

function usernameValidation(username){
    const pattern = /^[a-zA-Z][a-zA-Z0-9]{3,14}$/;
    return pattern.test(username);
}

const usernameInput = document.querySelector('#signup-username');
function checkUsernameStatus(value){
    return value.trim() === 'user0'? "EXISTS" : "NEW";
}

function setValidInput(input){
    input.classList.add('is-valid');
    input.classList.remove('is-invalid');
    const feedback = input.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.textContent = '';
        feedback.classList.add('d-none');
    }
  
    

}
function setInvalidInput(input, customMsg){
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        const feedback = input.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
    feedback.classList.remove('d-none');
    if (customMsg) feedback.textContent = customMsg;
}
}

usernameInput.addEventListener('input', ()=> {
    const usernameValue = usernameInput.value;
    if(!usernameValidation(usernameValue) ){
        setInvalidInput(usernameInput);
    }else{
        setValidInput(usernameInput);
    }

})

function toggleShowPass(input){
    return input.type === 'password'? input.type = 'text' : input.type = 'password';
}
const showPassSignUpBtn = document.querySelector('#show-signUp-password');
const signUpPassInput = document.querySelector('#signup-password');
showPassSignUpBtn.addEventListener('click', ()=>{
    toggleShowPass(signUpPassInput);
})

const showConfirmPass = document.querySelector('#show-confirm-password');
const confirmPassInput = document.querySelector('#confirm-password');
showConfirmPass.addEventListener('click', ()=>{
    toggleShowPass(confirmPassInput);
})

function checkPassValidity(pass){
    const pattern = /^(?=.*[a-z])(?=.*\d)[A-Za-z\d]{12,}$/;
    return pattern.test(pass);
}

signUpPassInput.addEventListener('input', ()=> {
    const passValue = signUpPassInput.value;
    return (!checkPassValidity(passValue))?
        setInvalidInput(signUpPassInput):
        setValidInput(signUpPassInput);
})

confirmPassInput.addEventListener('input', ()=> {
    return (!(signUpPassInput.value === confirmPassInput.value))?
        setInvalidInput(confirmPassInput):
        setValidInput(confirmPassInput);
})
const form = document.querySelector('#myForm');
const signUpBtn = document.querySelector('#signUp-pass');
signUpBtn.addEventListener('click', async (e)=> {
    e.preventDefault();
    if(checkValidateOf(usernameInput) && checkValidateOf(signUpPassInput) && checkValidateOf(confirmPassInput)){
        toggleLoading(signUpBtn, true);
        setTimeout(async ()=> {
        const finalPayload = {
        email : signUpData.email, 
        action: "register",
        username: usernameInput.value,
        password: signUpPassInput.value};
                
        const response = await checkFromServer(finalPayload);
            console.log(response.status);
        if(response.status === 'success'){
            const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success mt-3';
                alertDiv.id = 'added-success';
                alertDiv.textContent = 'Account created successfully!';
                form.appendChild(alertDiv);

            setTimeout(() => {
                    alertDiv.remove(); 
                    MapsTo('state-identity'); 
                }, 2000);
        }else if (response.status ==='error_username' || response.status ==='error_email') {
        setInvalidInput(usernameInput, response.message);
    }
        toggleLoading(signUpBtn, false);
    
        },1000)
        
    
    }

    
})


function toggleLoading(btn, isWaiting){
    const btnTxt = btn.textContent;
    if(isWaiting){
        btn.setAttribute('data-original-text', btn.innerHTML);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    }else{
        btn.disabled = false;
        const originalTxt = btn.getAttribute('data-original-text');
        if (originalTxt) btn.innerHTML = originalTxt;
        
    }
}
async function checkFromServer(data) {
    try {
        const response = await fetch('process.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });

        const text = await response.text(); // Get raw text first
        try {
            return JSON.parse(text); // Try to turn it into JSON
        } catch (err) {
            console.error("SERVER DIED WITH THIS ERROR:", text); // This will show the <br><b>...
            return "error";
        }
    } catch (error) {
        console.error(error);
        return "error";
    }
}


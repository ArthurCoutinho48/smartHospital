$(document).ready(() =>{
    $("#login").on("click", (e) => {
        e.preventDefault()
    
        checkForm()
       /* console.log('cheguei')*/
    })
})

//Nova validadação após o preemchimento dos campos e enquanto digita

$('#email').blur(function(){
    checkInputEmail()
})

$('#email').keyup(function(){
    checkInputEmail()
})

$('#password').blur(function(){
    checkInputPassword()
})

$('#password').keyup(function(){
    checkInputPassword()
})

// Validação do preencimento dos inputs

function checkInputEmail(){
    const emailValue = $('#email').val()

    if (emailValue === ''){
        errorInput("#textEmail", "#erroEmail", "Preencha seu email!")
    }else{
        $("#textEmail").removeClass('erro').addClass('success')
    }
}

function checkInputPassword(){
    const passwordValue = $('#password').val()

    if (passwordValue === ''){
        errorInput("#textPass", "#erroPass", 'A senha é obrigatoria!')
    }else{
        $("#textPass").removeClass('erro').addClass('success')
    }
}

//Checagem do formulário

function checkForm(){
    checkInputEmail()
    checkInputPassword()

    if($('#textEmail').hasClass('textfielde success') && $('#textPass').hasClass('textfielde success')){
        
        let form = $('#formLogin').serialize();

        $.ajax({
            type: 'post',
            url: '/autenticar',
            data: form,
            success: dados =>{
                 if(dados == '/home'){
                   /*
                    $("#carregamentoLoading").removeClass('hide');
                    
                    setTimeout(function(){
                        $("#carregamentoLoading").addClass('hide');
                    }, 600);

                    setTimeout(function(){
                        toggleModal()
                    }, 650);*/

                    window.location.href = '/home'

                }else if(dados == '/erro'){

                    errorInput("#textUser", "#erroUser", "Usuário/Senha incorretos ou não cadastrado!")

                    errorInput("#textPass", "#erroPass", "Usuário/Senha incorretos ou não cadastrado!")
                }    
                
            },
            error: erro =>{
                console.log(erro)
            }
        })

    }
}

//Mensagem de erro

function errorInput(groupInput, inputMessage, message){
    $(inputMessage).html(message)
    $(groupInput).removeClass('success').addClass('erro')
}
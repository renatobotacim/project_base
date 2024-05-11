<div style="text-align: center; background-color: #00c84b; padding: 20px 0px;">
    <img src="https://ticketk.com.br/logo_ticketK.png" alt="logo">
</div>
<!-- <%body%> -->
<div style="border:1px solid #00c84b; padding: 15px;">
    <h2>Cadastro Realizado</h2>
    <p>Olá seu cadastro foi realizado.</p>
    @if(isset($data['sendPass']))
        <p>
            Sua senha para acesso é: {{$data['pass']}}
            <br>
            <br>
            Recomendamos que você ao logar, atualize sua senha.
        </p>
    @endif
    <p>
        Primeiro é necessário validar sua conta.
    </p>
    <a href="{{$data['token']}}">{{$data['token']}}</a>
    @if(isset($data['link']))
        <br><br>
        <a href="{{$data['link']}}">
        <button
            class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
            Acessar Plataforma
        </button>
    </a>
    @endif
    <br><br>
    <p>Atenciosamente,<br>TicketK</p>
</div>

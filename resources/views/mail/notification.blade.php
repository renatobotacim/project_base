<div style="text-align: center; background-color: #00c84b; padding: 20px 0px;">
    <img src="https://ticketk.com.br/logo_ticketK.png" alt="logo">
</div>
<!-- <%body%> -->
<div style="border:1px solid #00c84b; padding: 15px;">
    <h2>{{$data['title']}}</h2>
    <p>{!!$data['content']!!}</p>
    @if(isset($data['link']))
        <p>{{$data['link']}}</p>
    @endif
    <br><br>
    <p>Atenciosamente,<br>TicketK</p>
</div>

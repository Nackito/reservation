<div>
  <h1>Admin Chat Box</h1>

  <ul>
    @foreach ($messages as $message)
    <li>
      <strong>De :</strong> {{ $message->sender->name }} <br>
      <strong>Message :</strong> {{ $message->content }} <br>
      <strong>Reçu :</strong> {{ $message->created_at->format('d/m/Y H:i') }}
    </li>
    @endforeach
  </ul>
</div>
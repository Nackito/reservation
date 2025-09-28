<script>
  window.__ECHO__ = {
    driver: 'pusher',
    key: @json(config('broadcasting.connections.pusher.key')),
    cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
    scheme: 'https',
    port: 443,
  };
</script>
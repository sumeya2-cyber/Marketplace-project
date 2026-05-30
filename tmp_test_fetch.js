const http = require('http');
const data = JSON.stringify({listing_type:'product',item_id:'TEST',quantity:1,guest_name:'Test',guest_email:'test@example.com',guest_token:'GUEST-1'});
const options = {
  hostname: 'localhost', port: 80, path: '/MarketPlace/php/api/create_order.php', method: 'POST',
  headers: {'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(data)}
};
const req = http.request(options, res => {
  let body = '';
  res.on('data', chunk => body += chunk);
  res.on('end', () => { console.log('STATUS', res.statusCode); console.log(body); });
});
req.on('error', console.error);
req.write(data);
req.end();

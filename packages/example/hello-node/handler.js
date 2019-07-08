let fs = require('fs');

let data=JSON.parse(fs.readFileSync('/dev/stdin'));
// console.log(data);

let text = data.greeting + ', ' + data.name;
let res = {
  "text": text
}
console.log(res);

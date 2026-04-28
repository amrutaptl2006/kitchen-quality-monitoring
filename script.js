const t=document.getElementById('temp')
const h=document.getElementById('hum')
const y=document.getElementById('hyg')
const s=document.getElementById('status')
const c=document.getElementById('clock')

let labels=[],d1=[],d2=[]

const ch1=new Chart(document.getElementById('chart1'),{
type:'line',data:{labels:labels,datasets:[{label:'Temp',data:d1}]}
})

const ch2=new Chart(document.getElementById('chart2'),{
type:'line',data:{labels:labels,datasets:[{label:'Humidity',data:d2}]}
})

function update(){
let now=new Date()
c.innerText=now.toLocaleTimeString()

let temp=(Math.random()*10+20).toFixed(1)
let hum=(Math.random()*20+40).toFixed(1)
let hyg=Math.floor(Math.random()*20+80)

t.innerText=temp
h.innerText=hum
y.innerText=hyg
s.innerText=hyg<85?'⚠ Alert':'✅ Good'

if(labels.length>10){labels.shift();d1.shift();d2.shift()}
labels.push(now.toLocaleTimeString())
d1.push(temp)
d2.push(hum)

ch1.update()
ch2.update()
}

setInterval(update,2000)

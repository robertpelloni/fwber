cd fwber-backend-ts
node --experimental-vm-modules dist/index.js > ../server_out.txt 2>&1 &
PID=$!
sleep 3
kill $PID
cat ../server_out.txt

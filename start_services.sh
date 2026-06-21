#!/bin/bash
cd fwber-backend-ts
npm start > ../backend.log 2>&1 &
BACKEND_PID=$!
cd ../fwber-frontend
npm start > ../frontend.log 2>&1 &
FRONTEND_PID=$!
echo $BACKEND_PID > ../backend.pid
echo $FRONTEND_PID > ../frontend.pid
echo "Services started."

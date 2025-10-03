#!/bin/bash

# Queue Management Script for Production

# Function to start queue worker
start_queue() {
    echo "Starting queue worker..."
    php artisan queue:work --timeout=600 --memory=512 --sleep=3 --tries=3 --max-jobs=100 --max-time=3600 &
    echo "Queue worker started with PID: $!"
}

# Function to stop queue worker
stop_queue() {
    echo "Stopping queue workers..."
    pkill -f "artisan queue:work"
    echo "Queue workers stopped"
}

# Function to restart queue worker
restart_queue() {
    echo "Restarting queue workers..."
    stop_queue
    sleep 2
    start_queue
}

# Function to check queue status
status_queue() {
    echo "Checking queue status..."
    php artisan queue:monitor
}

# Function to clear failed jobs
clear_failed() {
    echo "Clearing failed jobs..."
    php artisan queue:flush
    echo "Failed jobs cleared"
}

# Main script logic
case "$1" in
    start)
        start_queue
        ;;
    stop)
        stop_queue
        ;;
    restart)
        restart_queue
        ;;
    status)
        status_queue
        ;;
    clear-failed)
        clear_failed
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status|clear-failed}"
        exit 1
        ;;
esac

exit 0

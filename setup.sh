#!/bin/bash

# Issues Board Application Setup Script
# This script automates the complete setup process for development

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    local missing_requirements=()
    
    # Check for required commands
    if ! command_exists php; then
        missing_requirements+=("PHP 8.1+")
    else
        php_version=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
        if [ "$(printf '%s\n' "8.1" "$php_version" | sort -V | head -n1)" != "8.1" ]; then
            missing_requirements+=("PHP 8.1+ (current: $php_version)")
        fi
    fi
    
    if ! command_exists composer; then
        missing_requirements+=("Composer")
    fi
    
    if ! command_exists node; then
        missing_requirements+=("Node.js 16+")
    else
        node_version=$(node -v | cut -d. -f1 | cut -c2-)
        if [ "$node_version" -lt 16 ]; then
            missing_requirements+=("Node.js 16+ (current: v$node_version)")
        fi
    fi
    
    if ! command_exists npm; then
        missing_requirements+=("npm")
    fi
    
    if ! command_exists mysql; then
        print_warning "MySQL client not found. You'll need MySQL server running."
    fi
    
    # Report missing requirements
    if [ ${#missing_requirements[@]} -ne 0 ]; then
        print_error "Missing requirements:"
        for req in "${missing_requirements[@]}"; do
            echo "  - $req"
        done
        echo ""
        echo "Please install missing requirements and run this script again."
        exit 1
    fi
    
    print_success "All requirements satisfied!"
}

# Function to setup Laravel backend
setup_backend() {
    print_status "Setting up Laravel backend..."
    
    # Create backend directory if it doesn't exist
    if [ ! -d "backend" ]; then
        print_status "Creating Laravel project..."
        composer create-project laravel/laravel backend
        cd backend
    else
        print_status "Laravel project already exists, updating..."
        cd backend
    fi
    
    # Install additional dependencies
    print_status "Installing Laravel dependencies..."
    composer require pusher/pusher-php-server
    composer require --dev laravel/reverb
    
    # Copy environment file
    if [ ! -f .env ]; then
        cp .env.example .env
        print_status "Environment file created"
    fi
    
    # Generate application key
    print_status "Generating application key..."
    php artisan key:generate
    
    # Configure environment
    print_status "Configuring environment..."
    
    # Database configuration
    sed -i.bak 's/DB_DATABASE=laravel/DB_DATABASE=issues_board/' .env
    sed -i.bak 's/DB_USERNAME=root/DB_USERNAME=your_username/' .env
    sed -i.bak 's/DB_PASSWORD=/DB_PASSWORD=your_password/' .env
    
    # Broadcasting configuration
    sed -i.bak 's/BROADCAST_DRIVER=log/BROADCAST_DRIVER=reverb/' .env
    
    # Add Reverb configuration if not exists
    if ! grep -q "REVERB_APP_ID" .env; then
        echo "" >> .env
        echo "# Reverb WebSocket Configuration" >> .env
        echo "REVERB_APP_ID=local" >> .env
        echo "REVERB_APP_KEY=local" >> .env
        echo "REVERB_APP_SECRET=local" >> .env
        echo "REVERB_HOST=localhost" >> .env
        echo "REVERB_PORT=8080" >> .env
        echo "REVERB_SCHEME=http" >> .env
    fi
    
    # Queue configuration
    sed -i.bak 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/' .env
    
    print_success "Backend configured successfully!"
    cd ..
}

# Function to setup React frontend
setup_frontend() {
    print_status "Setting up React frontend..."
    
    if [ ! -d "frontend" ]; then
        print_status "Creating React application..."
        npx create-react-app frontend
        cd frontend
    else
        print_status "React application already exists, updating..."
        cd frontend
    fi
    
    # Install additional dependencies
    print_status "Installing frontend dependencies..."
    npm install axios pusher-js react-router-dom
    npm install -D tailwindcss postcss autoprefixer @tailwindcss/forms
    
    # Initialize Tailwind CSS
    if [ ! -f tailwind.config.js ]; then
        print_status "Initializing Tailwind CSS..."
        npx tailwindcss init -p
    fi
    
    # Create src/index.css for Tailwind
    cat > src/index.css << EOF
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom styles */
body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

code {
  font-family: source-code-pro, Menlo, Monaco, Consolas, 'Courier New',
    monospace;
}
EOF
    
    print_success "Frontend configured successfully!"
    cd ..
}

# Function to setup database
setup_database() {
    print_status "Setting up database..."
    
    # Check if MySQL is running
    if ! pgrep -x "mysqld" > /dev/null; then
        print_warning "MySQL server doesn't appear to be running."
        print_status "Please start MySQL server manually and run:"
        print_status "  mysql -u root -p -e \"CREATE DATABASE IF NOT EXISTS issues_board;\""
        return
    fi
    
    # Try to create database
    print_status "Creating database..."
    mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS issues_board;" 2>/dev/null || {
        print_warning "Could not create database automatically."
        print_status "Please create the database manually:"
        print_status "  mysql -u root -p -e \"CREATE DATABASE IF NOT EXISTS issues_board;\""
    }
    
    # Run migrations and seeds
    cd backend
    print_status "Running database migrations..."
    php artisan migrate || {
        print_warning "Migrations failed. Please check database configuration."
    }
    
    print_status "Seeding database with sample data..."
    php artisan db:seed || {
        print_warning "Seeding failed. You can run 'php artisan db:seed' manually later."
    }
    
    cd ..
    print_success "Database setup completed!"
}

# Function to create startup script
create_startup_script() {
    print_status "Creating startup script..."
    
    cat > start-dev.sh << 'EOF'
#!/bin/bash

# Development Server Startup Script
# Starts all required services for the Issues Board application

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Function to check if port is in use
check_port() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null; then
        return 0
    else
        return 1
    fi
}

# Check for conflicting processes
print_status "Checking for conflicting processes..."

if check_port 8000; then
    print_warning "Port 8000 is already in use. Please stop the process or change the port."
fi

if check_port 8080; then
    print_warning "Port 8080 is already in use. Please stop the process or change the port."
fi

if check_port 3000; then
    print_warning "Port 3000 is already in use. Please stop the process or change the port."
fi

# Start services
print_success "Starting Issues Board development environment..."
echo ""
print_status "Starting services in separate terminal windows..."
print_status "Press Ctrl+C to stop all services"
echo ""

# Function to start Laravel server
start_laravel() {
    cd backend
    print_status "Starting Laravel server on http://localhost:8000"
    php artisan serve
}

# Function to start Reverb WebSocket server
start_reverb() {
    cd backend
    print_status "Starting Reverb WebSocket server on ws://localhost:8080"
    php artisan reverb:start
}

# Function to start queue worker
start_queue() {
    cd backend
    print_status "Starting Laravel queue worker"
    php artisan queue:work --verbose --tries=3 --timeout=90
}

# Function to start React frontend
start_frontend() {
    cd frontend
    print_status "Starting React development server on http://localhost:3000"
    npm start
}

# Start all services in background
start_laravel &
LARAVEL_PID=$!

sleep 2
start_reverb &
REVERB_PID=$!

sleep 2
start_queue &
QUEUE_PID=$!

sleep 2
start_frontend &
FRONTEND_PID=$!

# Wait for all processes
wait $LARAVEL_PID $REVERB_PID $QUEUE_PID $FRONTEND_PID

print_status "All services stopped."
EOF
    
    chmod +x start-dev.sh
    print_success "Startup script created: ./start-dev.sh"
}

# Function to display final instructions
show_final_instructions() {
    echo ""
    print_success "🎉 Setup completed successfully!"
    echo ""
    print_status "Next steps:"
    echo "1. Update database credentials in backend/.env file"
    echo "2. Run './start-dev.sh' to start all services"
    echo ""
    print_status "Services will be available at:"
    echo "  • Frontend: http://localhost:3000"
    echo "  • Backend API: http://localhost:8000/api/v1"
    echo "  • WebSocket: ws://localhost:8080"
    echo ""
    print_status "Useful commands:"
    echo "  • Run tests: cd backend && php artisan test"
    echo "  • View logs: cd backend && tail -f storage/logs/laravel.log"
    echo "  • Reset database: cd backend && php artisan migrate:fresh --seed"
    echo ""
    print_warning "Don't forget to:"
    echo "  • Update .env file with your actual database credentials"
    echo "  • Configure CORS settings for production deployment"
    echo "  • Set up proper environment variables for production"
    echo ""
}

# Main execution
main() {
    clear
    echo "=================================================================="
    echo "         Issues Board Application Setup Script"
    echo "=================================================================="
    echo ""
    
    # Run setup steps
    check_requirements
    setup_backend
    setup_frontend
    setup_database
    create_startup_script
    show_final_instructions
}

# Run main function
main "$@"
# Use Node.js 20
FROM node:20-alpine

# Set working directory
WORKDIR /projects

# Install dependencies first for better caching
COPY package*.json ./
RUN npm install -g @angular/cli && npm install

# Copy project files
COPY . .

import bcrypt

users = {}  # This will store user data temporarily
login_attempts = {}

def create_account(username, password):
    hashed = bcrypt.hashpw(password.encode(), bcrypt.gensalt())
    users[username] = hashed
    return "Account created successfully"

def login(username, password):
    if username in login_attempts and login_attempts[username] >= 5:
        return "Account locked due to too many failed attempts."
    
    if username in users:
        stored_hashed = users[username]
        if bcrypt.checkpw(password.encode(), stored_hashed):
            login_attempts[username] = 0
            return "Login successful"
        else:
            login_attempts[username] = login_attempts.get(username, 0) + 1
            return "Login failed"
    else:
        return "User not found"
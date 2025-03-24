import logging
import os
from datetime import datetime

def setup_logging(username):
    log_filename = f"{username}_chat_{datetime.now().strftime('%Y%m%d_%H%M%S')}.log"
    logging.basicConfig(filename=log_filename, level=logging.INFO)

def log_message(message):
    logging.info(message)
#!/usr/bin/python
#import ipdb
import os
import mysql.connector
from mysql.connector import errorcode

if not os.getenv('DB_PASSWORD'):
    print ( "DB_PASSWORD needed in environment")
    exit()

if not os.getenv('DB_HOST'):
    print ( "DB_HOST needed in environment")
    exit()

if not os.getenv('DB_USER'):
    print ( "DB_HOST needed in environment")
    exit()

if not os.getenv('DB_DATABASE'):
    print ( "DB_DATABASE needed in environment")
    exit()

try:
    conn = mysql.connector.connect( host=os.getenv('DB_HOST'),
                                    database=os.getenv('DB_DATABASE'),
                                    user=os.getenv('DB_USER'),
                                    passwd=os.getenv('DB_PASSWORD'))
    conn.autocommit = False
    cursor = conn.cursor()
    cursor.execute('''CREATE TABLE IF NOT EXISTS conference_mapping(
                            db_id BIGINT(20) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                            room_name VARCHAR(255) NOT NULL,
                            room_number VARCHAR(255) NULL,
                            room_pin VARCHAR(255) NULL,
                            meet_instance VARCHAR(255) NULL,
                            creation_time DATETIME NOT NULL,
                            long_term TINYINT(1) NOT NULL DEFAULT 0,
                            mail_owner VARCHAR(255) NULL
                            )''')
    # Commit the change
    conn.commit()
# Catch the exception
except mysql.connector.Error as error:
    # Roll back any change if something goes wrong
    print("Failed to update record to database rollback: {}".format(error))
    conn.rollback()
    raise error
finally:
    # Close the db connection
    cursor.close()
    conn.close()
    print("connection is closed")

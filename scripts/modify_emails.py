# Take a list of emails addresses from a CSV and produce a CSV with the email address in column 1 and with a modified version of the email address in column 2 with a zero-width space (ZWS) character inserted before each . character.

# Open the file with the email addresses and skip the header row
with open('email_addresses.csv', 'r') as f:
    next(f)
    email_addresses = f.read()

# Split the email addresses into a list
email_addresses = email_addresses.split('\n')

# Convert the email addresses into a dictionary with the email address as the key and the modified email address as the value
email_addresses_dict = {}

for email_address in email_addresses:
    email_addresses_dict[email_address] = email_address.replace('.', '&#8203;.')

# Write the headers first then write dictionary to a CSV file
with open('email_addresses_with_zws.csv', 'w') as f:
    f.write('email address,ModifiedEmail\n')
    for key in email_addresses_dict.keys():
        f.write("%s,%s\n"%(key,email_addresses_dict[key]))

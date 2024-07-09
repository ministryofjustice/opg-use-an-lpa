import os
from notifications_python_client.notifications import NotificationsAPIClient
from notifications_python_client.errors import HTTPError
import argparse

notify_api_key = str(os.getenv('NOTIFY_API_KEY', ''))
if not notify_api_key:
    print("NOTIFY_API_KEY environment variable needs to be set")
    exit()

notifications_client = NotificationsAPIClient(notify_api_key)

def send_msg(email_address, succeeded_file, failed_file):
    try:
        response = notifications_client.send_email_notification(
            email_address=email_address,
            template_id='5c19af5c-a49c-4284-8758-9e67394b1a0c',
            personalisation={
              "EmailAddress": email_address,
            }
        )
        #print(response)
        print(f"Sending to {email_address} succeeded \n")
        succeeded_file.write(f"{email_address}\n")
    except HTTPError as e:
        print(f"Sending to {email_address} failed \n")
        failed_file.write(f"{email_address}\n")
        print(e)
        
def main():
    parser = argparse.ArgumentParser(description="Send bulk emails via Notify.")
    parser.add_argument(
        "--file",
        default="default",
        help="The file containing the email addresses to send to",
    )
    args = parser.parse_args()
    filename = args.file

    with open(filename + '_succeeded', 'w') as succeeded_file:
        with open(filename + '_failed', 'w') as failed_file:
            with open(args.file) as f:
                for line_terminated in f:
                    email_address = line_terminated.rstrip('\n')
                    print(email_address)
                    send_msg(email_address, succeeded_file, failed_file)
    
if __name__ == "__main__":
    main()

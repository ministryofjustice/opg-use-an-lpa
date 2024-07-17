import os
from time import sleep
from notifications_python_client.notifications import NotificationsAPIClient
from notifications_python_client.errors import HTTPError
import argparse
import timeit

notify_api_key = str(os.getenv('NOTIFY_API_KEY', ''))
if not notify_api_key:
    print("NOTIFY_API_KEY environment variable needs to be set")
    exit()

notifications_client = NotificationsAPIClient(notify_api_key)

def send_msg(email_address, template_id, succeeded_file, failed_file):
    start_time = timeit.default_timer()
    try:
        response = notifications_client.send_email_notification(
            email_address=email_address,
            template_id=template_id,
            personalisation={
              "EmailAddress": email_address,
            }
        )
        elapsed = timeit.default_timer() - start_time
        print(f"Sending to {email_address} succeeded , time {elapsed}\n")
        succeeded_file.write(f"{email_address}\n")
    except HTTPError as e:
        print(f"Sending to {email_address} failed \n")
        failed_file.write(f"{email_address}\n")
        print(e)
        
def main():
    parser = argparse.ArgumentParser(description="Send bulk emails via Notify.")
    parser.add_argument(
        "--file",
        help="The file containing the email addresses to send to",
        required=True,
    )
    parser.add_argument(
        "--template",
        help="The notify template to send",
        required=True,
    )
    args = parser.parse_args()
    filename = args.file
    template = args.template

    with open(filename + '_succeeded', 'w') as succeeded_file:
        with open(filename + '_failed', 'w') as failed_file:
            with open(args.file) as f:
                for line_terminated in f:
                    email_address = line_terminated.rstrip('\n')
                    send_msg(email_address, template, succeeded_file, failed_file)
                    # sleep in order not to exceed rate limit
                    # we were using 0.03, which limits us to 2000 per min not including time to call api
                    # this is now commented as the api round-trip time alone keeps us within the rate limit
                    #sleep(0.03)
    
if __name__ == "__main__":
    main()

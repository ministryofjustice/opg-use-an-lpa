###Update needs reset

These scripts can be modified to find certain log records with the `log_extraction.py` script 
and then we can use those records to update dynamodb with a flag based on the results using `update_needs_reset`.

In this case we are creating a list of user emails and updating a flag called `NeedsReset`, if useful we 
could expand on these scripts to make them more generic. We don't assume roles in these scripts so they need to be 
run inside cloud9.
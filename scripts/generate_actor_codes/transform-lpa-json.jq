to_entries
| map_values({
  ActorLPAId :.key
  | sub( "(?<a>7\\d{3})(?<b>\\d{4})(?<c>\\d{4})";"\(.a)-\(.b)-\(.c)")}
  + {
    	availableCodes: .value
    	| map_values(
    	{
    	    id,
            uId : .uId
                | sub( "(?<a>7\\d{3})(?<b>\\d{4})(?<c>\\d{4})";"\(.a)-\(.b)-\(.c)"),
            firstname,
            middlenames,
            surname,
            code : .code
                | sub( "(?<a>[A-Z0-9]{4})(?<b>[A-Z0-9]{4})(?<c>[A-Z0-9]{4})";"\(.a) \(.b) \(.c)"),
            expiry
    	})
	}
)

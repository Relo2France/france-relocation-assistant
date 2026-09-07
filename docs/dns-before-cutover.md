# relo2france.com DNS, as of 2026-09-07

Captured before any nameserver move. Email breaks if MX is not recreated
at the new provider BEFORE the nameservers change - that is the one
irreversible-feeling failure in this whole cutover.

## A
```
relo2france.com.	177	IN	A	192.0.78.222
relo2france.com.	177	IN	A	192.0.78.171
```

## TXT
```
relo2france.com.	3600	IN	TXT	"v=spf1 include:_spf.wpcloud.com ~all"
```

## NS
```
relo2france.com.	85944	IN	NS	ns1.wordpress.com.
relo2france.com.	85944	IN	NS	ns2.wordpress.com.
relo2france.com.	85944	IN	NS	ns3.wordpress.com.
```

## SOA
```
relo2france.com.	299	IN	SOA	ns1.wordpress.com. hostmaster.wordpress.com. 2025121702 14400 7200 604800 300
```

## Common subdomains
```
www.relo2france.com.	14400	IN	CNAME	relo2france.com.
relo2france.com.	300	IN	A	192.0.78.222
relo2france.com.	300	IN	A	192.0.78.171
```

## Where the site actually lives
```
WordPress.com Atomic (x-ac header shows _atomic_)
relo2france.wordpress.com -> 301 -> https://relo2france.com/  (the site's WP.com address)
apex A: 192.0.78.222
apex A: 192.0.78.171
```

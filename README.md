# ttbag

Tiny Tiny Bag is a TT-RSS (Tiny Tiny RSS) plugin.

The plugin aims at extending TT-RSS to (also) make it a "Read it later", Pocket, Wallabag alike thing,
i.e. it allows to store arbitrary articles (off a RSS feed) to maybe read it somewhen later.

After all it is a mesh-up of different stuff that was already there

* it hooks TT-RSS' "share anything" feature, which by default just stores a URL, its title and
  some manually entered content
* Tiny Tiny Bag therefore uses "Full-Text RSS" aka fivefilters content-only to scrape the
  pure article content from the shared pages
* opposed to standard "share anything" behaviour Tiny Tiny bag neither immediately marks the
  article as read nor shares (re-publishes) it


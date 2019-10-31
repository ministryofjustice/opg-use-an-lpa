const cheerio = require("cheerio");
const handlebars = require("handlebars");

const htmlParser = async (html, data) => {
  const $ = cheerio.load(html);
  $("a").each(function() {
    $(this).replaceWith($(this).html());
  });

  const parsedHtml = $.root().html();
  let template = handlebars.compile(parsedHtml);

  const result = template(data);

  return result;
};

export default htmlParser;

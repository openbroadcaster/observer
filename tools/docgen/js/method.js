function toggleMethodVisible(elem)
{
  let container = $(elem).closest('.doc-method-hidden');

  if (container.hasClass('is-hidden')) {
    container.removeClass('is-hidden').addClass('is-visible');
    $(elem).html('&ndash;');
  } else {
    container.removeClass('is-visible').addClass('is-hidden');
    $(elem).text('+');
  }
}

<?php
/**
 * certificate_authority()
 *
 * @category function
 * @author Nirmalakhanza
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $distro
 * @return string
 * 
 */
function certificate_authority($distro)
{

  $ca_path = null;

  if ($distro == 'Debian' || $distro == 'Ubuntu' || $distro == 'Gentoo' || $distro == 'Arch' || $distro == 'Slackware') {

    $ca_path = '/etc/ssl/certs/ca-certificates.crt';

  } elseif ($distro == 'Fedora' || $distro == 'RedHat' || $distro == 'CentOS' || $distro == 'Mageia' || $distro == 'Vercel' || $distro == 'Netlify') {

    $ca_path = '/etc/pki/tls/certs/ca-bundle.crt';

  } elseif ($distro == 'Alpine') {

    $ca_path = '/etc/ssl/cert.pem';

  } elseif ($distro == 'OpenSUSE') {

    $ca_path = '/etc/ssl/ca-bundle.pem';

  } elseif ($distro == 'MacOS' || $distro == 'FreeBSD' || $distro == 'OpenBSD') {

    $ca_path = '/etc/ssl/cert.pem';

  } else {

    $ca_path = null;
  }

  return $ca_path;
}
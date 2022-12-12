<?php

namespace Drupal\purl\Plugin\Purl\Method;

use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Site\Settings;
use Drupal\purl\Annotation\PurlMethod;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @PurlMethod(
 *     id="subdomain",
 *     name="Subdomain",
 *     stages={
 *        Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PROCESS_OUTBOUND,
 *        Drupal\purl\Plugin\Purl\Method\MethodInterface::STAGE_PRE_GENERATE
 *     }
 * )
 */
class SubdomainMethod extends MethodAbstract implements MethodInterface, ContainerAwareInterface, PreGenerateHookInterface {

  use ContainerAwareTrait;

  public function contains(Request $request, $modifier) {
    $baseHost = $this->getBaseHost();

    if (!$baseHost) {
      return FALSE;
    }

    $host = $request->getHost();

    if ($host === $this->getBaseHost()) {
      return FALSE;
    }

    return $this->hostContainsModifier($modifier, $request->getHost());
  }

  private function hostContainsModifier($modifier, $host) {
    return strpos($host, $modifier . '.') === 0;
  }

  private function getBaseHost() {
    // Retrieve this from request context.
    return Settings::get('purl_base_domain');
  }

  /**
   * @return RequestContext
   */
  private function getRequestContext() {
    return $this->container->get('router.request_context');
  }

  public function enterContext($modifier, $path, array &$options) {
    $baseHost = $this->getBaseHost();

    // Can't do anything if this is not set.
    if (!$baseHost) {
      return NULL;
    }

    $currentHost = isset($options['host']) ? $options['host'] : $this->getRequestContext()->getHost();

    $pattern = '#^(.+)\.' . preg_quote($baseHost, '#') . '$#';

    $matches = [];
    preg_match_all($pattern, $currentHost, $matches);

    if (count($matches[0]) === 0) {
      return NULL;
    }

    $subdomains = explode('.', $matches[1][0]);

    if (in_array($modifier, $subdomains)) {
      return NULL;
    }

    $subdomains[] = $modifier;
    $subdomains = array_values(array_filter($subdomains));

    $options['absolute'] = TRUE;

    if (count($subdomains)) {
      $options['host'] = sprintf('%s.%s', implode('.', $subdomains), $baseHost);
    }
    else {
      $options['host'] = $baseHost;
    }

    return $path;
  }

  public function exitContext($modifier, $path, array &$options) {

    $baseHost = $this->getBaseHost();

    // Can't do anything if this is not set.
    if (!$baseHost) {
      return NULL;
    }

    $currentHost = isset($options['host']) ? $options['host'] : $this->getRequestContext()->getHost();

    $pattern = '#^(.+)\.' . preg_quote($baseHost, '#') . '$#';

    $matches = [];
    preg_match_all($pattern, $currentHost, $matches);

    if (count($matches[0]) === 0) {
      return NULL;
    }

    $subdomain = implode('.', array_filter(explode('.', $matches[1][0]), function ($m) use ($modifier) {
      return $m !== $modifier;
    }));

    $options['absolute'] = TRUE;

    if ($subdomain) {
      $options['host'] = sprintf('%s.%s', $subdomain, $baseHost);
    }
    else {
      $options['host'] = $baseHost;
    }

    return $path;
  }

  public function preGenerateEnter($modifier, $name, &$parameters, &$options, $collect_bubblable_metadata = FALSE) {
    $this->enterContext($modifier, '', $options);
  }

  public function preGenerateExit($modifier, $name, &$parameters, &$options, $collect_bubblable_metadata = FALSE) {
    $this->exitContext($modifier, '', $options);
  }

}
